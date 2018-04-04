<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.ReservedThroughput)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method \ots\protocolbuffer\CapacityUnit getCapacityUnit()
 * @method void setCapacityUnit(\ots\protocolbuffer\CapacityUnit $value)
 */
class ReservedThroughput extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.ReservedThroughput)
  
  /**
   * @var \ots\protocolbuffer\CapacityUnit $capacity_unit
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $capacity_unit;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.ReservedThroughput)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.ReservedThroughput)

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
        "name"     => "capacity_unit",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\CapacityUnit',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.ReservedThroughput)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}