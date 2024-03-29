<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.BatchWriteRowResponse)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method array getTables()
 * @method void appendTables(\ots\protocolbuffer\TableInBatchWriteRowResponse $value)
 */
class BatchWriteRowResponse extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.BatchWriteRowResponse)
  
  /**
   * @var array $tables
   * @tag 1
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   * @see \ots\protocolbuffer\TableInBatchWriteRowResponse
   **/
  protected $tables;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.BatchWriteRowResponse)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.BatchWriteRowResponse)

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
        "message" => '\ots\protocolbuffer\TableInBatchWriteRowResponse',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.BatchWriteRowResponse)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
