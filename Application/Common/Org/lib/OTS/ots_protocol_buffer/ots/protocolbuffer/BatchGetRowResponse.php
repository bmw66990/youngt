<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.BatchGetRowResponse)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method array getTables()
 * @method void appendTables(\ots\protocolbuffer\TableInBatchGetRowResponse $value)
 */
class BatchGetRowResponse extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.BatchGetRowResponse)
  
  /**
   * @var array $tables
   * @tag 1
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\TableInBatchGetRowResponse
   *
   * same indices w.r.t. request
   *
   **/
  protected $tables;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.BatchGetRowResponse)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.BatchGetRowResponse)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "tables",
        "required" => false,
        "optional" => false,
        "repeated" => true,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\TableInBatchGetRowResponse',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.BatchGetRowResponse)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
